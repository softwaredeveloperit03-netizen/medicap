import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { DeptheadhrComponent } from './deptheadhr/deptheadhr.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DeptheadhrComponent},

{ path: 'leave', loadChildren: () => import('./leave/leave.module').then(m=>m.LeaveModule), data: {preload: false}},
]
@NgModule({
  declarations: [DeptheadhrComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class VpModule { }
