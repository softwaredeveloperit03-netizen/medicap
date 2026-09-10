import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RecvingComponent } from './recving.component';

describe('RecvingComponent', () => {
  let component: RecvingComponent;
  let fixture: ComponentFixture<RecvingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RecvingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RecvingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
