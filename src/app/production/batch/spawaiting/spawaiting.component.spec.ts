import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SpawaitingComponent } from './spawaiting.component';

describe('SpawaitingComponent', () => {
  let component: SpawaitingComponent;
  let fixture: ComponentFixture<SpawaitingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SpawaitingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SpawaitingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {                                                                   
    expect(component).toBeTruthy();
  });
});
