import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';

import { DashbordComponent } from './dashbord/dashbord.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';



const routes:Routes = [
  {path:'',component:DashbordComponent},
  {path:'new',component:NewComponent},
 
];
@NgModule({
  declarations: [
    DashbordComponent,
    NewComponent
  ],
  imports: [ TranslateModule,
    CommonModule
  ]
})
export class Challan1Module { }
