 


 import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
 
import { NewComponent } from './new/new.component';
import { ImpactComponent } from './impact/impact.component';
import { OfficereviewComponent } from './officereview/officereview.component';
import { TranslateModule } from '@ngx-translate/core';

 


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
 
  { path: 'impact', component: ImpactComponent },
  { path: 'officereview', component: OfficereviewComponent },
 
 
];

@NgModule({
  declarations: [
    DashboardComponent,NewComponent, ImpactComponent, OfficereviewComponent
 
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class PChangecontrolModule { }
