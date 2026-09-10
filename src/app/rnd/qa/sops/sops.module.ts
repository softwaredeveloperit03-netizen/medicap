import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashbordComponent } from './dashbord/dashbord.component';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { NewComponent } from './new/new.component';
import { FormasterComponent } from './formaster/formaster.component';
import { DraftComponent } from './draft/draft.component';
import { SopcheckingComponent } from './sopchecking/sopchecking.component';
import { RcontrolComponent } from './rcontrol/rcontrol.component';
import { LogComponent } from './log/log.component';
import { FinialsopComponent } from './finialsop/finialsop.component';
import { SoplogComponent } from './soplog/soplog.component';
import { RivisopComponent } from './rivisop/rivisop.component';
import { RicivcopyComponent } from './ricivcopy/ricivcopy.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashbordComponent},
  { path: 'new', component: NewComponent},
  { path: 'formaster', component: FormasterComponent},
  { path: 'draft', component: DraftComponent},
  { path: 'sopchecking', component: SopcheckingComponent},
  { path: 'rcontrol', component: RcontrolComponent},
  { path: 'log', component: LogComponent},
  { path: 'finialsop', component: FinialsopComponent},
  { path: 'soplog', component: SoplogComponent},
  { path: 'rivisop', component: RivisopComponent},
  { path: 'ricivcopy', component: RicivcopyComponent},
  { path: 'correction', loadChildren: () => import('./correction/correction.module').then(m=>m.CorrectionModule), data: {preload: false}},

         
  ];

@NgModule({
  declarations: [DashbordComponent, NewComponent, FormasterComponent, DraftComponent, SopcheckingComponent, RcontrolComponent, LogComponent, FinialsopComponent, SoplogComponent, RivisopComponent, RicivcopyComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class SopsModule { }
  