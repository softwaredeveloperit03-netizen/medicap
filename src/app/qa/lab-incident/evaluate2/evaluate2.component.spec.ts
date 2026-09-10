import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Evaluate2Component } from './evaluate2.component';

describe('Evaluate2Component', () => {
  let component: Evaluate2Component;
  let fixture: ComponentFixture<Evaluate2Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Evaluate2Component ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Evaluate2Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
