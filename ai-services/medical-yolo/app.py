from pathlib import Path
from tempfile import NamedTemporaryFile

from fastapi import FastAPI, File, Form, UploadFile
from ultralytics import YOLO

MODEL_PATH = Path("models/best.pt")

app = FastAPI(title="Medical YOLO Service")
model = YOLO(str(MODEL_PATH)) if MODEL_PATH.exists() else None


@app.get("/health")
def health():
    return {
        "status": "ok",
        "model_loaded": model is not None,
        "model_path": str(MODEL_PATH),
    }


@app.post("/predict")
async def predict(
    image: UploadFile = File(...),
    modality: str = Form("xray"),
    body_part: str | None = Form(None),
):
    if model is None:
        return {
            "summary": "YOLO model chua duoc cai dat. Hay dat file models/best.pt va khoi dong lai service.",
            "findings": [],
        }

    suffix = Path(image.filename or "image.jpg").suffix or ".jpg"

    with NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        tmp.write(await image.read())
        tmp_path = tmp.name

    results = model.predict(tmp_path, conf=0.25, imgsz=640, verbose=False)
    names = model.names
    findings = []

    for result in results:
        for box in result.boxes:
            cls = int(box.cls[0])
            xyxy = [round(float(value), 2) for value in box.xyxy[0].tolist()]
            confidence = round(float(box.conf[0]), 4)
            findings.append(
                {
                    "label": names.get(cls, str(cls)) if isinstance(names, dict) else names[cls],
                    "confidence": confidence,
                    "bbox": xyxy,
                }
            )

    if findings:
        summary = f"AI phat hien {len(findings)} vung nghi ngo tren anh {modality}."
    else:
        summary = "AI chua phat hien vung bat thuong ro rang. Bac si can doc phim va xac nhan."

    return {
        "summary": summary,
        "findings": findings,
        "modality": modality,
        "body_part": body_part,
    }
