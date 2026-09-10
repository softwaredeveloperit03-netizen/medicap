import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { OpeningComponent } from './opening/opening.component';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes = [
  {path:'',component:NewComponent},
  {path:'opening',component:OpeningComponent},
];

@NgModule({
  declarations: [NewComponent, OpeningComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class StockModule { }
