import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { OpeningComponent } from './opening/opening.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DropdownModule } from 'primeng/dropdown';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { NewrmstockComponent } from './newrmstock/newrmstock.component';
import { StockStatusComponent } from './stock-status/stock-status.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'opening', component: OpeningComponent},
  { path: 'NEW STOCK', component: NewrmstockComponent},
  { path: 'stock-status', component: StockStatusComponent},
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},
  { path: 'packing', loadChildren: () => import('./packing/packing.module').then(m=>m.PackingModule), data: {preload: false}},
  { path: 'finish', loadChildren: () => import('./finish/finish.module').then(m=>m.FinishModule), data: {preload: false}},
]

@NgModule({
  declarations: [DashboardComponent, OpeningComponent, NewrmstockComponent,StockStatusComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    AutoCompleteModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
})
export class StockModule { }
