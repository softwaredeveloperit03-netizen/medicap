import { AfterViewInit, Component, ElementRef, OnDestroy, Renderer2 } from '@angular/core';
import { VolumetricLogModalService } from './volumetric-log-modal.service';

@Component({
  selector: 'app-volumetric-log-modals',
  templateUrl: './volumetric-log-modals.component.html',
  styleUrls: ['./volumetric-log-modals.component.css'],
})
export class VolumetricLogModalsComponent implements AfterViewInit, OnDestroy {
  private movedToBody = false;

  constructor(
    readonly modals: VolumetricLogModalService,
    private host: ElementRef<HTMLElement>,
    private renderer: Renderer2
  ) {}

  ngAfterViewInit(): void {
    const el = this.host.nativeElement;
    if (el?.parentElement && el.parentElement !== document.body) {
      this.renderer.appendChild(document.body, el);
      this.movedToBody = true;
    }
  }

  ngOnDestroy(): void {
    const el = this.host.nativeElement;
    if (this.movedToBody && el?.parentNode === document.body) {
      this.renderer.removeChild(document.body, el);
    }
  }
}
