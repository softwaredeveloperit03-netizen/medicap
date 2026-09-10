import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UnitFormulaComponent } from './unit-formula.component';

describe('UnitFormulaComponent', () => {
  let component: UnitFormulaComponent;
  let fixture: ComponentFixture<UnitFormulaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UnitFormulaComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(UnitFormulaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
