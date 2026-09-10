import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'log', component: LogComponent },
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, LogComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class EmployeeInitialsSignatureModule {}
