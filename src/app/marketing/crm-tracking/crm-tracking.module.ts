import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { EmailNewComponent } from './email/new/new.component';
import { EmailLogComponent } from './email/log/log.component';
import { EmailFollowupComponent } from './email/followup/followup.component';
import { PhoneNewComponent } from './phone/new/new.component';
import { PhoneLogComponent } from './phone/log/log.component';
import { PhoneFollowupComponent } from './phone/followup/followup.component';
import { PhoneUpdateComponent } from './phone/update/update.component';
import { New2Component } from './phone/new2/new2.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'email/new', component: EmailNewComponent },
  { path: 'email/log', component: EmailLogComponent },
  { path: 'email/followup', component: EmailFollowupComponent },
  { path: 'phone/new', component: PhoneNewComponent },
  { path: 'phone/update/:id', component: PhoneUpdateComponent },
  { path: 'phone/log', component: PhoneLogComponent },
  { path: 'phone/followup', component: PhoneFollowupComponent },
  { path: 'phone/new2', component: New2Component },
];

@NgModule({
  declarations: [
    DashboardComponent,
    EmailNewComponent,
    EmailLogComponent,
    EmailFollowupComponent,
    PhoneNewComponent,
    PhoneUpdateComponent,
    PhoneLogComponent,
    PhoneFollowupComponent,
    New2Component
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ],
  providers: [DatePipe]
})
export class CrmTrackingModule { }


