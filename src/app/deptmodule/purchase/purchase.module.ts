import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { NgxDocViewerModule } from 'ngx-doc-viewer';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { PoapprovalComponent } from './poapproval/poapproval.component';
import { PologComponent } from './polog/polog.component';
import { RegistrationStatusComponent } from './regulatory-new/registration-status/registration-status.component';
import { TranslateModule } from '@ngx-translate/core';






const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'poapproval', component: PoapprovalComponent},
  { path: 'polog', component: PologComponent},
  {
    path: 'indend',
    loadChildren: () => import('./indent/indent.module').then((m) => m.IndentModule),
    data: { preload: false },
  },
  { path: 'purchase', redirectTo: 'indend', pathMatch: 'full' },
  { path: 'indent', redirectTo: 'indend', pathMatch: 'prefix' },
];



@NgModule({
  declarations: [
    DashboardComponent,
    PoapprovalComponent,
    PologComponent,
    RegistrationStatusComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    NgxDocViewerModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PurchaseModule { }
