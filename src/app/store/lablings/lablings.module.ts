import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HomeComponent } from './home/home.component';
  import { RouterModule, Routes } from '@angular/router';
 import { DropdownModule } from 'primeng/dropdown';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: HomeComponent},
 ]

@NgModule({
  declarations: [
    HomeComponent,
    ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
   
})
export class LablingsModule { }
