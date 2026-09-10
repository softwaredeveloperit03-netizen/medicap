import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RawComponent } from './raw/raw.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { PackingComponent } from './packing/packing.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'raw', component: RawComponent},
  { path: 'packing', component: PackingComponent}
];

@NgModule({
  declarations: [RawComponent, DashboardComponent, PackingComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TestingModule { }
