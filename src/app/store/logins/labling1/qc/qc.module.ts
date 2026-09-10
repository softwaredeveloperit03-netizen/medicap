import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashbordComponent } from './dashbord/dashbord.component';
import { SaplingComponent } from './sapling/sapling.component';
import { TestingComponent } from './testing/testing.component';
  import { RouterModule, Routes } from '@angular/router';
 import { DropdownModule } from 'primeng/dropdown';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashbordComponent},
  { path: 'sapling', component: SaplingComponent},
  { path: 'testing', component: TestingComponent},
 
 
]

@NgModule({
  declarations: [
    DashbordComponent,
    SaplingComponent,
    TestingComponent,

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
export class QcModule { }
