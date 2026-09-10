import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { IdentificationComponent } from './identification/identification.component';
import { AnnouncementComponent } from './announcement/announcement.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'identification', component: IdentificationComponent},
  { path: 'announcement', component: AnnouncementComponent},
  { path: 'log', component: LogComponent},
  
];


@NgModule({
  declarations: [
    DashboardComponent,IdentificationComponent,AnnouncementComponent,LogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class FiretrainingModule { }
