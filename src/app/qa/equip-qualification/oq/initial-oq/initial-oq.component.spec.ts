import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InitialOqComponent } from './initial-oq.component';

describe('InitialOqComponent', () => {
  let component: InitialOqComponent;
  let fixture: ComponentFixture<InitialOqComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InitialOqComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InitialOqComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
