import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { HistoryComponent } from './history/history.component';
import { HomeComponent } from './home/home.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'history', component: HistoryComponent},
  { path: 'new', component: NewComponent},
 ]
  ;

@NgModule({
  declarations: [ 
    NewComponent,
    HistoryComponent,
    HomeComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CorrectionModule { }
