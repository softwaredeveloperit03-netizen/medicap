import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RecevingComponent } from './receving.component';

describe('RecevingComponent', () => {
  let component: RecevingComponent;
  let fixture: ComponentFixture<RecevingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RecevingComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(RecevingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
