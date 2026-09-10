import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PlanmacroComponent } from './planmacro.component';

describe('PlanmacroComponent', () => {
  let component: PlanmacroComponent;
  let fixture: ComponentFixture<PlanmacroComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PlanmacroComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PlanmacroComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
