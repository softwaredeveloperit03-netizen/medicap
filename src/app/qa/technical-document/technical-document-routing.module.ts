import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { DocumentComponent } from './document/document.component';
import { TechnicalDocumentComponent } from './technical-document/technical-document.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', redirectTo: 'document', pathMatch: 'full'},
  { path: 'technical-document', component: TechnicalDocumentComponent },
  { path: 'document', component: DocumentComponent },

];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class TechnicalDocumentRoutingModule { }
