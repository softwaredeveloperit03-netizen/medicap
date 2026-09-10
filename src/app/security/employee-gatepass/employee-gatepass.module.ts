import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';

import { AppModule } from "../../app.module";
const routes: Routes = [
  { path: '', component: DashboardComponent}
]


@NgModule({
    declarations: [
        DashboardComponent
    ],
    imports: [
    SharedModule, TranslateModule,
        CommonModule,
        FormsModule,
        RouterModule.forChild(routes),
        AppModule,
        
    ]
})
export class EmployeeGatepassModule { }
