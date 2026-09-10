import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RawComponent } from './raw/raw.component';
import { PackingComponent } from './packing/packing.component';
import { ChemicalComponent } from './chemical/chemical.component';
import { GlasswareComponent } from './glassware/glassware.component';
import { GeneralComponent } from './general/general.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { PurchaseReportComponent } from './purchase-report/purchase-report.component';
import { QuotationComponent } from './quotation/quotation.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'raw', component: RawComponent},
  { path: 'packing', component: PackingComponent},
  { path: 'chemical', component: ChemicalComponent},
  { path: 'glassware', component: GlasswareComponent},
  { path: 'general', component: GeneralComponent},
  { path: 'purchase-report', component: PurchaseReportComponent},
  { path: 'quotation', component: QuotationComponent},
];

@NgModule({
  declarations: [DashboardComponent, RawComponent, PackingComponent, ChemicalComponent, GlasswareComponent, GeneralComponent, PurchaseReportComponent, QuotationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PurchaseModule { }
