import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { HttpClientModule } from '@angular/common/http';
import { MultiSelectModule } from 'primeng/multiselect';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ChecklistComponent } from './checklist/checklist.component';
import { AddChecklistComponent } from './add-checklist/add-checklist.component';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes=[
  {path: 'new', component: NewComponent},
  {path: 'checklist', component: ChecklistComponent},
  {path: 'add-checklist', component: AddChecklistComponent},
  {path: '', component:  DashboardComponent},
]

@NgModule({
  declarations: [
    NewComponent,
    DashboardComponent,
    ChecklistComponent,
    AddChecklistComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    HttpClientModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class MasterSpecModule { }
