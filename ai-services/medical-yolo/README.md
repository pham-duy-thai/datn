# Medical YOLO Service

Service FastAPI de Laravel goi phan tich anh y te bang YOLO.

## Chay service

```bash
cd ai-services/medical-yolo
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
mkdir models
```

Dat model da train vao:

```text
ai-services/medical-yolo/models/best.pt
```

Chay:

```bash
uvicorn app:app --host 127.0.0.1 --port 9000
```

Laravel goi endpoint:

```text
POST http://127.0.0.1:9000/predict
```

## Train YOLO goi y

Can dataset co bounding box theo format YOLO. Vi du `data.yaml`:

```yaml
path: datasets/chest-xray
train: images/train
val: images/val
names:
  0: pneumonia
  1: pleural_effusion
  2: cardiomegaly
  3: nodule
  4: fracture
```

Lenh train mau:

```bash
yolo detect train model=yolo11n.pt data=data.yaml epochs=50 imgsz=640
```

Sau khi train, copy file:

```text
runs/detect/train/weights/best.pt
```

vao:

```text
ai-services/medical-yolo/models/best.pt
```

Luu y: Ket qua YOLO chi ho tro sang loc/gan nhan vung nghi ngo. Bac si la nguoi doc phim va ket luan cuoi cung.
