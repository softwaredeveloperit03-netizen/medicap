import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashbordComponent } from './dashbord/dashbord.component';
import { GrnComponent } from './grn/grn.component';
  import { RouterModule, Routes } from '@angular/router';
 import { DropdownModule } from 'primeng/dropdown';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashbordComponent},
  { path: 'grn', component: GrnComponent},
  { path: 'qc', loadChildren: () => import('./qc/qc.module').then(m=>m.QcModule), data: {preload: false}},

 
]

@NgModule({
  declarations: [
    DashbordComponent,
    GrnComponent,
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
export class Labling1Module { }
