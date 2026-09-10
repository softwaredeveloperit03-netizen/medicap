import { NgModule } from '@angular/core';
import { SharedModule } from 'src/app/shared/shared.module';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';

import {RouterModule,Routes} from "@angular/router";
import {FormsModule} from "@angular/forms";
import{ClarityModule} from "@clr/angular";

const routes: Routes = [
  {path:'',component:DashboardComponent},
  {path:'new',component:NewComponent},
];


@NgModule({
  declarations: [DashboardComponent, NewComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    RouterModule.forChild(routes),
    FormsModule,
    ClarityModule
  ]
})
export class InprocessModule { }
