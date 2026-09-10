import {
  Component,
  Input,
  Output,
  EventEmitter,
  ChangeDetectorRef,
  ViewChild,
  ElementRef,
  AfterViewInit,
  OnChanges,
  SimpleChanges,
  OnDestroy
} from '@angular/core';

export interface CapturedImage {
  imageAsDataUrl: string;
}

@Component({
  selector: 'app-camera',
  templateUrl: './camera.component.html',
  styleUrls: ['./camera.component.css']
})
export class CameraComponent implements AfterViewInit, OnChanges, OnDestroy {
  @ViewChild('videoEl') videoRef: ElementRef<HTMLVideoElement>;
  @ViewChild('canvasEl') canvasRef: ElementRef<HTMLCanvasElement>;

  /** Stream from parent (getUserMedia called on button click there). */
  @Input() stream: MediaStream | null = null;

  @Output() pictureTaken = new EventEmitter<CapturedImage>();
  @Output() useUploadPhotoRequested = new EventEmitter<void>();

  isStreaming = false;
  errorMessage = '';

  constructor(private cdr: ChangeDetectorRef) {}

  ngAfterViewInit(): void {
    this.attachStream();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['stream'] && this.videoRef?.nativeElement) {
      this.attachStream();
    }
  }

  ngOnDestroy(): void {
    const v = this.videoRef?.nativeElement;
    if (v?.srcObject) {
      v.srcObject = null;
    }
    this.isStreaming = false;
  }

  private attachStream(): void {
    const video = this.videoRef?.nativeElement;
    if (!video || !this.stream) return;
    video.srcObject = this.stream;
    video.play()
      .then(() => {
        this.isStreaming = true;
        this.errorMessage = '';
        this.cdr.detectChanges();
      })
      .catch((err) => {
        this.errorMessage = err?.message || 'Video play failed';
        this.cdr.detectChanges();
      });
  }

  capture(): void {
    const video = this.videoRef?.nativeElement;
    const canvas = this.canvasRef?.nativeElement;
    if (!video || !canvas || !this.isStreaming) return;
    const w = video.videoWidth;
    const h = video.videoHeight;
    if (w === 0 || h === 0) {
      this.errorMessage = 'Wait for video to load';
      this.cdr.detectChanges();
      return;
    }
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.drawImage(video, 0, 0, w, h);
    try {
      const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
      this.pictureTaken.emit({ imageAsDataUrl: dataUrl });
    } catch {
      this.errorMessage = 'Could not capture image';
      this.cdr.detectChanges();
    }
  }

  useUploadPhoto(): void {
    this.useUploadPhotoRequested.emit();
  }
}
