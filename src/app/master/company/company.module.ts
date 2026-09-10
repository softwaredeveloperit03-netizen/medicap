import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component:NewComponent}
  
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent
  ],
  imports: [ TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    SharedModule,
    RouterModule.forChild(routes)
  ]
})
export class CompanyModule { }
