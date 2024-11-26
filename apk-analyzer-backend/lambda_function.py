import subprocess
import os
import sys
import xml.etree.ElementTree as ET
import numpy as np
from joblib import load
import pandas as pd
import boto3
import logging

# Initialize logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger()

# Append custom numpy directory to sys.path
sys.path.append(os.path.join(os.path.dirname(__file__), 'numpy'))

def lambda_handler(event, context):
    # Step ++1: Fetch the uploaded file from S3
    bucket_name = "apk-analyzer-storage"
    try:
        object_key = event["Records"][0]["s3"]["object"]["key"]
    except KeyError:
        logger.error("KeyError: 'Records' key missing in the event.")
        raise

    s3_client = boto3.client("s3")
    temp_apk_file = "/tmp/uploaded_apk.apk"  # Temporary path to store the uploaded APK file

    try:
        s3_client.download_file(bucket_name, object_key, temp_apk_file)
        logger.info(f"Downloaded APK file from S3: {temp_apk_file}")
    except Exception as e:
        logger.error(f"Error downloading file: {e}")
        raise

    # Step 2: Decompile APK using APKTool
    apktool_bucket = "apk-analyzer-backend"
    apktool_key = "tools/apktool.jar"
    temp_apktool_jar = "/tmp/apktool.jar"

    try:
        s3_client.download_file(apktool_bucket, apktool_key, temp_apktool_jar)
        logger.info(f"Downloaded APKTool from S3: {temp_apktool_jar}")
    except Exception as e:
        logger.error(f"Error downloading APKTool: {e}")
        raise

    output_dir = "/tmp/decompiled_apk"
    command = ["java", "-jar", temp_apktool_jar, "d", temp_apk_file, "-o", output_dir, "--force"]
    try:
        subprocess.run(command, check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        logger.info("APK decompiled successfully.")
    except subprocess.CalledProcessError as e:
        logger.error(f"Error during APK decompilation: {e.stderr}")
        raise

    # Step 3: Analyze the APK
    features = [
        "SEND_SMS", "INTERNET", "WRITE_HISTORY_BOOKMARKS", "TelephonyManager.getSubscriberId",
        "TelephonyManager.getDeviceId", "GET_ACCOUNTS", "chmod", "android.telephony.gsm.SmsManager",
        "TelephonyManager.getLine1Number", "Ljava.net.URLDecoder", "android.intent.action.BOOT_COMPLETED",
        "READ_PHONE_STATE", "CHANGE_NETWORK_STATE", "WRITE_EXTERNAL_STORAGE", "Ljava.lang.Object.getClass",
        "Ljava.lang.Class.getCanonicalName", "ACCESS_COARSE_LOCATION", "android.content.pm.PackageInfo",
        "Ljava.lang.Class.cast", "onBind", "findClass", "WRITE_SETTINGS", "HttpGet.init", "ClassLoader",
    ]
    feature_presence = {feature: 0 for feature in features}

    # Analyze AndroidManifest.xml
    manifest_path = os.path.join(output_dir, "AndroidManifest.xml")
    if os.path.exists(manifest_path):
        logger.info("Analyzing AndroidManifest.xml...")
        tree = ET.parse(manifest_path)
        root = tree.getroot()
        for permission in root.findall(".//uses-permission"):
            permission_name = permission.attrib.get("{http://schemas.android.com/apk/res/android}name", "")
            normalized_name = permission_name.split(".")[-1]
            if normalized_name in features:
                feature_presence[normalized_name] = 1

    # Analyze .smali files
    for root, dirs, files in os.walk(output_dir):
        for file in files:
            if file.endswith(".smali"):
                file_path = os.path.join(root, file)
                with open(file_path, "r", encoding="utf-8") as smali_file:
                    content = smali_file.read()
                    for feature in features:
                        if feature in content:
                            feature_presence[feature] = 1

    # Step 4: Model predictions
    isolation_model_path = "/tmp/isolation_forest_model.pkl"
    lightgbm_model_path = "/tmp/optimal_lightgbm_model.pkl"

    try:
        s3_client.download_file(apktool_bucket, "models/isolation_forest_model.pkl", isolation_model_path)
        s3_client.download_file(apktool_bucket, "models/optimal_lightgbm_model.pkl", lightgbm_model_path)
    except Exception as e:
        logger.error(f"Error downloading models: {e}")
        raise

    isolation_model = load(isolation_model_path)
    lightgbm_model = load(lightgbm_model_path)

    # Prepare feature vector
    feature_vector = np.array([feature_presence[feature] for feature in features]).reshape(1, -1)
    feature_vector_df = pd.DataFrame(feature_vector, columns=features)

    # Make predictions
    isolation_prediction = isolation_model.predict(feature_vector_df)
    lightgbm_prediction = lightgbm_model.predict(feature_vector)

    isolation_result = "Malicious" if isolation_prediction[0] == -1 else "Benign"
    lightgbm_result = "Malicious" if lightgbm_prediction[0] == 1 else "Benign"

    final_result = "Malicious" if isolation_result == "Malicious" or lightgbm_result == "Malicious" else "Benign"

    logger.info(f"Isolation Forest Prediction: {isolation_result}")
    logger.info(f"LightGBM Prediction: {lightgbm_result}")
    logger.info(f"Final Prediction: {final_result}")

    return {
        "statusCode": 200,
        "body": {
            "isolation_result": isolation_result,
            "lightgbm_result": lightgbm_result,
            "final_result": final_result,
        }
    }
