import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewProductFormulationComponent } from './new-product-formulation.component';

describe('NewProductFormulationComponent', () => {
  let component: NewProductFormulationComponent;
  let fixture: ComponentFixture<NewProductFormulationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewProductFormulationComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(NewProductFormulationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
