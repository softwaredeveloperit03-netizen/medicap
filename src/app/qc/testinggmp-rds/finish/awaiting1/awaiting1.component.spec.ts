import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Awaiting1Component } from './awaiting1.component';

describe('Awaiting1Component', () => {
  let component: Awaiting1Component;
  let fixture: ComponentFixture<Awaiting1Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Awaiting1Component ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Awaiting1Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
