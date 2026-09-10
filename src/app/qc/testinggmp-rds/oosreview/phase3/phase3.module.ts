import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ReanalysisComponent } from './reanalysis/reanalysis.component';
import { HypoComponent } from './hypo/hypo.component';
import { DocsIconsModule } from '../../../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'hypo', component: HypoComponent},
  { path: 'Reanalysis', component: ReanalysisComponent},
 ]

@NgModule({
  declarations: [
    DashboardComponent,
    ReanalysisComponent,
    HypoComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class Phase3Module { }
