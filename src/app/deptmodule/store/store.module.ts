import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TestAtComponent } from './test-at/test-at.component';
import { DashboardComponent } from './dashboard/dashboard.component';

import { RouterModule, Routes } from '@angular/router';
import { NgxDocViewerModule } from 'ngx-doc-viewer';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},

  { path: 'levApproval', component: TestAtComponent},

 
 
];



@NgModule({
  declarations: [
    TestAtComponent,DashboardComponent
   ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    NgxDocViewerModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class StoreModule { }
