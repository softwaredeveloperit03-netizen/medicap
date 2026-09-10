import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LitProductFormulationComponent } from './lit-product-formulation.component';

describe('LitProductFormulationComponent', () => {
  let component: LitProductFormulationComponent;
  let fixture: ComponentFixture<LitProductFormulationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LitProductFormulationComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LitProductFormulationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
