import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RawComponent } from './raw/raw.component';
import { PackingComponent } from './packing/packing.component';
import { FinishComponent } from './finish/finish.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'raw', component: RawComponent},
  { path: 'packing', component: PackingComponent},
  { path: 'finish', component: FinishComponent}
];

@NgModule({
  declarations: [DashboardComponent, RawComponent, PackingComponent, FinishComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class SamplingModule { }
