import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RecforcastComponent } from './recforcast.component';

describe('RecforcastComponent', () => {
  let component: RecforcastComponent;
  let fixture: ComponentFixture<RecforcastComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RecforcastComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RecforcastComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
