import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { CheckrejComponent } from './checkrej/checkrej.component';
import { CheckapprComponent } from './checkappr/checkappr.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'rejection', component: CheckrejComponent},
  { path: 'approval', component: CheckapprComponent},
  
]

@NgModule({
  declarations: [
    DashboardComponent,CheckrejComponent,CheckapprComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class RawModule { }
