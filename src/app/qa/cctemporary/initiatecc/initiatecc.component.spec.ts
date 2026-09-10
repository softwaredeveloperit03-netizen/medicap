import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InitiateccComponent } from './initiatecc.component';

describe('InitiateccComponent', () => {
  let component: InitiateccComponent;
  let fixture: ComponentFixture<InitiateccComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InitiateccComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InitiateccComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
