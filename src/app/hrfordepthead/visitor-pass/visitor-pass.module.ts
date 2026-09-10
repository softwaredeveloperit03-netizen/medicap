import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CameraModule } from 'src/app/camera/camera.module';
import { TranslateModule } from '@ngx-translate/core';








const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
 
]


@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    CameraModule,
    RouterModule.forChild(routes)
  ]
})
export class VisitorPassModule { }
