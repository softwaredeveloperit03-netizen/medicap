import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { OperationComponent } from './operation/operation.component';
import { MagneticComponent } from './magnetic/magnetic.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'report', component: OperationComponent},
  { path: 'magnetic', component: MagneticComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    OperationComponent,
    MagneticComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class GaussmeterModule { }
