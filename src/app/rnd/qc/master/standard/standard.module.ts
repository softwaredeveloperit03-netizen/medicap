import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent}
];

@NgModule({
  declarations: [DashboardComponent, NewComponent],
  imports: [ TranslateModule,
    CommonModule,
    RouterModule.forChild(routes)
  ]
})
export class StandardModule { }
