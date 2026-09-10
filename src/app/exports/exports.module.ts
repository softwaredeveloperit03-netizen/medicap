import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { RouterModule, Routes } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AuthorizationComponent } from './authorization/authorization.component';
import { LogComponent } from './log/log.component'; 
import { CheckingComponent } from './checking/checking.component';
import { TranslateModule } from '@ngx-translate/core';
 

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'authorization', component: AuthorizationComponent },
  { path: 'checking', component: CheckingComponent },
  { path: 'log', component: LogComponent },
  {
    path: 'landed',
    loadChildren: () =>
      import('./landed/landed.module').then((m) => m.LandedModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [
    DashboardComponent,
    AuthorizationComponent,
    CheckingComponent, 
    LogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ],
  providers: [DatePipe]
})
export class ExportsModule { }
