import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashbordComponent } from './dashbord/dashbord.component';
import { NewComponent } from './new/new.component';
 import { RouterModule, Routes } from '@angular/router';
import { UtisComponent } from './utis/utis.component';
import { DropdownModule } from 'primeng/dropdown';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { EquipmentsComponent } from './equipments/equipments.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashbordComponent},
  { path: 'new', component: NewComponent},
  { path: 'utis', component: UtisComponent},
  { path: 'equipments', component: EquipmentsComponent},
  { path: 'lable', loadChildren: () => import('./labling1/labling1.module').then(m=>m.Labling1Module), data: {preload: false}},

]

@NgModule({
  declarations: [
    DashbordComponent,
    NewComponent,
    UtisComponent,
    EquipmentsComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
   
})
export class LoginsModule { }
