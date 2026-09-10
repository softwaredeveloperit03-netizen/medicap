import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UtensilComponent } from './utensil.component';

describe('UtensilComponent', () => {
  let component: UtensilComponent;
  let fixture: ComponentFixture<UtensilComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UtensilComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(UtensilComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
