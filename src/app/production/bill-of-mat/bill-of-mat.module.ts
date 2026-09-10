import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { UnitforComponent } from './unitfor/unitfor.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { BomComponent } from './bom/bom.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path:'',component:DashboardComponent},
  { path:'unitformula',component:UnitforComponent},
  { path: 'bomm' ,component:BomComponent}
];


@NgModule({
  declarations: [
    UnitforComponent,
    DashboardComponent,
    BomComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class BillOfMatModule { }
