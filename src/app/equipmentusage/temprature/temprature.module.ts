import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common'; 
import {  FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { DashbaordComponent } from './dashbaord/dashbaord.component';
import { NewComponent } from './new/new.component';
import { BarcodeComponent } from './barcode/barcode.component';
import { CheckingComponent } from './checking/checking.component';
import { TranslateModule } from '@ngx-translate/core';
import { LogComponent } from './log/log.component';


const routes: Routes = [
 
  { path: '', component: DashbaordComponent},
  { path: 'New', component: NewComponent},
    { path: 'barcodess', component: BarcodeComponent},
    { path: 'checking', component: CheckingComponent},
    { path: 'log', component: LogComponent},
 
     


];

@NgModule({
  declarations: [
    DashbaordComponent ,NewComponent,BarcodeComponent, CheckingComponent, LogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TempratureModule { }
