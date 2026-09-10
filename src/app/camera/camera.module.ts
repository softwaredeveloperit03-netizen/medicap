import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CameraComponent } from './camera.component';
import { WebcamModule } from 'ngx-webcam';
import { TranslateModule } from '@ngx-translate/core';


@NgModule({
  declarations: [CameraComponent],
  imports: [ TranslateModule,CommonModule, WebcamModule],
  exports: [CameraComponent]
})
export class CameraModule { }
