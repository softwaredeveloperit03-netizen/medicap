import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AwaitingreviewComponent } from './awaitingreview.component';

describe('AwaitingreviewComponent', () => {
  let component: AwaitingreviewComponent;
  let fixture: ComponentFixture<AwaitingreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AwaitingreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AwaitingreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
