import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DossierreqComponent } from './dossierreq.component';

describe('DossierreqComponent', () => {
  let component: DossierreqComponent;
  let fixture: ComponentFixture<DossierreqComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DossierreqComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DossierreqComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
