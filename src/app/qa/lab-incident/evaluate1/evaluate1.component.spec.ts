import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Evaluate1Component } from './evaluate1.component';

describe('Evaluate1Component', () => {
  let component: Evaluate1Component;
  let fixture: ComponentFixture<Evaluate1Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Evaluate1Component ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Evaluate1Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
