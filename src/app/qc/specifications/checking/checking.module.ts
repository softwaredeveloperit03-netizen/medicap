import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common'; 
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from 'src/app/qc/specifications/checking/dashboard/dashboard.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', component: DashboardComponent},
]

@NgModule({
  declarations: [
    DashboardComponent
  ],
  imports: [ TranslateModule,
    CommonModule,
    RouterModule.forChild(routes)
  ]
})
export class CheckingModule { }
