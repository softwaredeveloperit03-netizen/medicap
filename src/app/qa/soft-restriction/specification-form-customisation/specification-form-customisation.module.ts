import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { ViewComponent } from './view/view.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'approval', component: ApprovalComponent },
  { path: 'view/:scope/:id', component: ViewComponent },
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ApprovalComponent, ViewComponent],
  imports: [
    SharedModule,CommonModule, FormsModule, TranslateModule, ClarityModule, RouterModule.forChild(routes)],
})
export class SpecificationFormCustomisationModule {}
