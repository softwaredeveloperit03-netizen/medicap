import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RegisterComponent } from './register/register.component';
import { Routes, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'register', component: RegisterComponent },
  { path: 'new', component: NewComponent },
];

@NgModule({
  declarations: [NewComponent, DashboardComponent, RegisterComponent],
  imports: [SharedModule, TranslateModule, CommonModule, FormsModule, ClarityModule, RouterModule.forChild(routes)],
})
export class VehicleManagementModule {}
