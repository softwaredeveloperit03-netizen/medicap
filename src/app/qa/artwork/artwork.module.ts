import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { PreparationComponent } from './preparation/preparation.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { ObsoluteComponent } from './obsolute/obsolute.component';
import { RejectedComponent } from './rejected/rejected.component';
import { ClarityModule } from '@clr/angular';
import { ShadeComponent } from './shade/shade.component';
import { UploadComponent } from './upload/upload.component';
import { ArtworklogComponent } from './artworklog/artworklog.component';
import {RejectlogComponent} from './rejectlog/rejectlog.component';
import {ArtworkinactiveComponent} from './artworkinactive/artworkinactive.component';
import { TranslateModule } from '@ngx-translate/core';

import { NewuploadComponent } from './newupload/newupload.component'

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'preparation' , component: PreparationComponent},
  { path: 'approval', component:ApprovalComponent},
  { path: 'log', component:LogComponent},
  { path: 'shade', component: ShadeComponent},
  { path: 'obsolute', component:ObsoluteComponent},
  { path: 'upload', component:UploadComponent},
  { path: 'artworklog', component:ArtworklogComponent},
  { path: 'rejectlog', component:RejectlogComponent},
  { path: 'artworkinactive', component:ArtworkinactiveComponent},
  { path: 'rejection', component:RejectedComponent},
  { path: 'design', loadChildren: () => import('./design/design.module').then(m=>m.DesignModule), data: {preload: false}},
  { path: 'printing', loadChildren: () => import('./printing/printing.module').then(m=>m.PrintingModule), data: {preload: false}},
  { path: 'registration', loadChildren: () => import('./registration/registration.module').then(m=>m.RegistrationModule), data: {preload: false}}
  
];

@NgModule({
  declarations: [DashboardComponent, PreparationComponent, ApprovalComponent, LogComponent,ObsoluteComponent,RejectedComponent, ShadeComponent, UploadComponent, ArtworklogComponent,RejectlogComponent,ArtworkinactiveComponent, NewuploadComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ArtworkModule { }
