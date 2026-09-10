import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { InhouseComponent } from './inhouse/inhouse.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'inhouse', component: InhouseComponent},
  { path: 'newinhouse', component: NewComponent},
  { path: 'external', loadChildren: () => import('./external/external.module').then(m=>m.ExternalModule)},
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, InhouseComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class TrainersModule { }
