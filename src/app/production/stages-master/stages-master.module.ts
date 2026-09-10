import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { RouterModule, Routes } from '@angular/router';
import {FormsModule} from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { CheckingComponent } from './checking/checking.component';
import { TranslateModule } from '@ngx-translate/core';

import { EditComponent } from './edit/edit.component'
import { ApproveComponent } from './approve/approve.component'

const routes: Routes = [
  {path: '', component: DashboardComponent},
  {path: 'checking', component: CheckingComponent},
  {path: 'approval', component: ApproveComponent},
  {path: 'edit', component: EditComponent},
  {path: 'new', component: NewComponent}
]

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    CheckingComponent,
    ApproveComponent,
    EditComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class StagesMasterModule { }
