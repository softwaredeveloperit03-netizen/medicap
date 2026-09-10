import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { OutpassDashboardComponent } from './dashboard/dashboard.component';
import { OutpassNewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: OutpassDashboardComponent },
  { path: 'new', component: OutpassNewComponent }
];

@NgModule({
  declarations: [
    OutpassDashboardComponent,
    OutpassNewComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class OutpassModule {}
